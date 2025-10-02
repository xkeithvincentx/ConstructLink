<?php
// Start output buffering to capture content
ob_start();

// Get user data
$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
$reportData = $reportData ?? [];
?>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-calendar-range me-2"></i>Report Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=procurement/reports" class="row g-3">
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" 
                       class="form-control" 
                       id="date_from" 
                       name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? $reportData['period']['from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" 
                       class="form-control" 
                       id="date_to" 
                       name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? $reportData['period']['to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label for="vendor_id" class="form-label">Vendor</label>
                <select class="form-select" id="vendor_id" name="vendor_id">
                    <option value="">All Vendors</option>
                    <?php if (isset($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?= $vendor['id'] ?>" 
                                    <?= ($_GET['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vendor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php if (isset($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Generate
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportReport()">
                        <i class="bi bi-file-earmark-excel"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center border-primary">
            <div class="card-body">
                <i class="bi bi-box display-4 text-primary"></i>
                <h3 class="mt-2"><?= number_format(count($reportData['acquisitions'] ?? [])) ?></h3>
                <p class="text-muted mb-0">Total Acquisitions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <i class="bi bi-currency-dollar display-4 text-success"></i>
                <h3 class="mt-2"><?= formatCurrency($reportData['summary']['total_spending'] ?? 0) ?></h3>
                <p class="text-muted mb-0">Total Spending</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-info">
            <div class="card-body">
                <i class="bi bi-calculator display-4 text-info"></i>
                <h3 class="mt-2"><?= formatCurrency($reportData['summary']['average_cost'] ?? 0) ?></h3>
                <p class="text-muted mb-0">Average Cost</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-warning">
            <div class="card-body">
                <i class="bi bi-shop display-4 text-warning"></i>
                <h3 class="mt-2"><?= $reportData['summary']['unique_vendors'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Vendors Used</p>
            </div>
        </div>
    </div>
</div>

<!-- Breakdown Charts Row -->
<?php if (!empty($reportData['breakdowns'])): ?>
<div class="row mb-4">
    <!-- Vendor Breakdown -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Top Vendors by Spending</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData['breakdowns']['vendors'])): ?>
                    <?php foreach (array_slice($reportData['breakdowns']['vendors'], 0, 5) as $vendor): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars($vendor['vendor_name']) ?></div>
                            <small class="text-muted"><?= $vendor['assets_count'] ?> assets</small>
                        </div>
                        <span class="badge bg-primary"><?= formatCurrency($vendor['total_spending']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No vendor data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Top Categories by Spending</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData['breakdowns']['categories'])): ?>
                    <?php foreach (array_slice($reportData['breakdowns']['categories'], 0, 5) as $category): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars($category['category_name']) ?></div>
                            <small class="text-muted"><?= $category['assets_count'] ?> assets</small>
                        </div>
                        <span class="badge bg-info"><?= formatCurrency($category['total_spending']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No category data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Project Breakdown -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Top Projects by Spending</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData['breakdowns']['projects'])): ?>
                    <?php foreach (array_slice($reportData['breakdowns']['projects'], 0, 5) as $project): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars($project['project_name']) ?></div>
                            <small class="text-muted"><?= $project['assets_count'] ?> assets</small>
                        </div>
                        <span class="badge bg-success"><?= formatCurrency($project['total_spending']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No project data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Acquisitions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Acquisition Details
        </h5>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" onclick="printReport()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-outline-success" onclick="exportReport()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['acquisitions'])): ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm" id="acquisitionsTable">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Project</th>
                        <th>Maker</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['acquisitions'] as $asset): ?>
                    <tr>
                        <td>
                            <small><?= formatDate($asset['acquired_date']) ?></small>
                        </td>
                        <td>
                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                <strong><?= htmlspecialchars($asset['ref'] ?? 'N/A') ?></strong>
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($asset['name']) ?></div>
                            <?php if (!empty($asset['model'])): ?>
                                <small class="text-muted"><?= htmlspecialchars($asset['model']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($asset['vendor_name'])): ?>
                                <a href="?route=vendors/view&id=<?= $asset['vendor_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($asset['vendor_name']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($asset['project_name'])): ?>
                                <a href="?route=projects/view&id=<?= $asset['project_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($asset['project_name']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($asset['maker_name'])): ?>
                                <a href="?route=makers/view&id=<?= $asset['maker_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($asset['maker_name']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="text-success"><?= formatCurrency($asset['acquisition_cost'] ?? 0) ?></strong>
                        </td>
                        <td>
                            <span class="badge <?= getStatusBadgeClass($asset['status']) ?>">
                                <?= getStatusLabel($asset['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?route=assets/view&id=<?= $asset['id'] ?>" 
                                   class="btn btn-outline-primary btn-sm" 
                                   title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-info">
                    <tr>
                        <th colspan="7" class="text-end">Total:</th>
                        <th><?= formatCurrency($reportData['summary']['total_spending'] ?? 0) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">No acquisitions found</h5>
            <p class="text-muted">Try adjusting your date range or filters, or check if there are any assets in the system.</p>
            <div class="mt-3">
                <a href="?route=procurement/create" class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-1"></i>Procure First Asset
                </a>
                <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset Filters
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Export report to Excel
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?' + params.toString();
}

// Print report
function printReport() {
    window.print();
}

// Reset filters
function resetFilters() {
    window.location.href = '?route=procurement/reports';
}

// Initialize DataTable if available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#acquisitionsTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']], // Sort by date descending
            columnDefs: [
                { targets: [7], type: 'currency' }, // Cost column
                { targets: [8, 9], orderable: false }   // Status and Actions columns
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
                    className: 'btn btn-danger btn-sm'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i>Print',
                    className: 'btn btn-primary btn-sm'
                }
            ]
        });
    }
});

// Quick date range selectors
function setDateRange(days) {
    const today = new Date();
    const fromDate = new Date(today.getTime() - (days * 24 * 60 * 60 * 1000));
    
    document.getElementById('date_from').value = fromDate.toISOString().split('T')[0];
    document.getElementById('date_to').value = today.toISOString().split('T')[0];
}

// Add quick date range buttons
document.addEventListener('DOMContentLoaded', function() {
    const filterCard = document.querySelector('.card-body form');
    if (filterCard) {
        const quickRangeDiv = document.createElement('div');
        quickRangeDiv.className = 'col-12 mt-2';
        quickRangeDiv.innerHTML = `
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(7)">Last 7 days</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(30)">Last 30 days</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(90)">Last 3 months</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(365)">Last year</button>
            </div>
        `;
        filterCard.appendChild(quickRangeDiv);
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Procurement Reports - ConstructLink™';
$pageHeader = 'Procurement Reports';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement', 'url' => '?route=procurement'],
    ['title' => 'Reports', 'url' => '?route=procurement/reports']
];

// Page actions
$pageActions = '<a href="?route=procurement" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back to Procurement
</a>';

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
