<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-arrow-up-right me-2"></i>
        Withdrawal Report
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
        <form method="GET" action="?route=reports/withdrawals" class="row g-3">
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
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="released" <?= ($_GET['status'] ?? '') === 'released' ? 'selected' : '' ?>>Released</option>
                    <option value="returned" <?= ($_GET['status'] ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
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
                <h3><?= $reportData['summary']['total_withdrawals'] ?? 0 ?></h3>
                <p class="mb-0 small">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="bi bi-clock display-4 mb-2"></i>
                <h3><?= $reportData['summary']['pending_withdrawals'] ?? 0 ?></h3>
                <p class="mb-0 small">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['released_withdrawals'] ?? 0 ?></h3>
                <p class="mb-0 small">Released</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="bi bi-arrow-return-left display-4 mb-2"></i>
                <h3><?= $reportData['summary']['returned_withdrawals'] ?? 0 ?></h3>
                <p class="mb-0 small">Returned</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['overdue_withdrawals'] ?? 0 ?></h3>
                <p class="mb-0 small">Overdue</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <i class="bi bi-calendar display-4 mb-2"></i>
                <h3><?= number_format($reportData['summary']['avg_days_out'] ?? 0, 1) ?></h3>
                <p class="mb-0 small">Avg Days</p>
            </div>
        </div>
    </div>
</div>

<!-- Withdrawal Details -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Withdrawal Details</h6>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" onclick="filterOverdue()">
                <i class="bi bi-exclamation-triangle me-1"></i>Show Overdue
            </button>
            <button class="btn btn-outline-secondary" onclick="showAll()">
                <i class="bi bi-list me-1"></i>Show All
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['withdrawals'])): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="withdrawalsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Project</th>
                            <th>Receiver</th>
                            <th>Status</th>
                            <th>Withdrawn By</th>
                            <th>Created</th>
                            <th>Expected Return</th>
                            <th class="text-center">Days Out</th>
                            <th class="text-center">Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['withdrawals'] as $withdrawal): ?>
                            <tr class="<?= $withdrawal['days_overdue'] > 0 ? 'table-warning' : '' ?>">
                                <td>
                                    <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="text-decoration-none">
                                        #<?= $withdrawal['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['asset_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($withdrawal['project_name']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($withdrawal['receiver_name']) ?></td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'bg-warning',
                                        'released' => 'bg-success',
                                        'returned' => 'bg-info',
                                        'canceled' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$withdrawal['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst($withdrawal['status']) ?>
                                    </span>
                                    <?php if ($withdrawal['days_overdue'] > 0): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="small"><?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></div>
                                        <?php if ($withdrawal['released_by_name']): ?>
                                            <small class="text-muted">Released by: <?= htmlspecialchars($withdrawal['released_by_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="small"><?= date('M j, Y', strtotime($withdrawal['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('g:i A', strtotime($withdrawal['created_at'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($withdrawal['expected_return']): ?>
                                        <div class="<?= $withdrawal['days_overdue'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($withdrawal['days_out']): ?>
                                        <span class="badge <?= $withdrawal['days_out'] > 30 ? 'bg-warning' : 'bg-light text-dark' ?>">
                                            <?= $withdrawal['days_out'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($withdrawal['days_overdue'] > 0): ?>
                                        <span class="badge bg-danger">
                                            <?= $withdrawal['days_overdue'] ?> days
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($withdrawal['status'] === 'pending' && $auth->hasRole(['System Admin', 'Warehouseman'])): ?>
                                            <a href="?route=withdrawals/release&id=<?= $withdrawal['id'] ?>" 
                                               class="btn btn-outline-success" title="Release">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($withdrawal['status'] === 'released' && $auth->hasRole(['System Admin', 'Warehouseman'])): ?>
                                            <a href="?route=withdrawals/return&id=<?= $withdrawal['id'] ?>" 
                                               class="btn btn-outline-info" title="Mark Returned">
                                                <i class="bi bi-arrow-return-left"></i>
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
                <i class="bi bi-arrow-up-right display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No withdrawal data found</h5>
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
    window.location.href = '?route=reports/export&type=withdrawals&' + params.toString();
}

// Print report
function printReport() {
    window.print();
}

// Reset filters
function resetFilters() {
    window.location.href = '?route=reports/withdrawals';
}

// Filter overdue withdrawals
function filterOverdue() {
    const table = document.getElementById('withdrawalsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const overdueCell = row.cells[9];
        const hasOverdue = overdueCell.querySelector('.badge.bg-danger');
        
        if (hasOverdue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Show all withdrawals
function showAll() {
    const table = document.getElementById('withdrawalsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        row.style.display = '';
    });
}

// Initialize table interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for quick actions
    const table = document.getElementById('withdrawalsTable');
    if (table) {
        table.addEventListener('click', function(e) {
            if (e.target.closest('.btn-group')) {
                e.stopPropagation();
            }
        });
    }
    
    // Highlight overdue rows
    const overdueRows = document.querySelectorAll('.table-warning');
    overdueRows.forEach(row => {
        row.style.borderLeft = '4px solid #ffc107';
    });
});

// Auto-refresh every 2 minutes for real-time updates
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // Subtle indication of refresh
        const summaryCards = document.querySelectorAll('.card h3');
        summaryCards.forEach(card => {
            card.style.opacity = '0.8';
            setTimeout(() => {
                card.style.opacity = '1';
            }, 300);
        });
    }
}, 120000); // 2 minutes
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
