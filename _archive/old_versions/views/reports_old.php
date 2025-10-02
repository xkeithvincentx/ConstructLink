<?php
/**
 * ConstructLink™ Procurement Reports View
 * Procurement analytics and reports
 */

// Start output buffering to capture content
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-graph-up me-2"></i>
            Procurement Reports
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="?route=procurement" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Procurement
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-calendar-range me-2"></i>Report Period
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="?route=procurement/reports" class="row g-3">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= htmlspecialchars($reportData['period']['from'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= htmlspecialchars($reportData['period']['to'] ?? '') ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Generate Report
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportReport()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-box display-4 text-primary"></i>
                    <h3 class="mt-2"><?= number_format(count($reportData['acquisitions'] ?? [])) ?></h3>
                    <p class="text-muted">Total Acquisitions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-currency-dollar display-4 text-success"></i>
                    <h3 class="mt-2"><?= formatCurrency($reportData['total_spending'] ?? 0) ?></h3>
                    <p class="text-muted">Total Spending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-calculator display-4 text-info"></i>
                    <h3 class="mt-2"><?= formatCurrency(($reportData['total_spending'] ?? 0) / max(1, count($reportData['acquisitions'] ?? []))) ?></h3>
                    <p class="text-muted">Average Cost</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-shop display-4 text-warning"></i>
                    <h3 class="mt-2"><?= count(array_unique(array_column($reportData['acquisitions'] ?? [], 'vendor_name'))) ?></h3>
                    <p class="text-muted">Vendors Used</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Acquisitions Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Acquisition Details</h5>
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
                <table class="table table-hover" id="acquisitionsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>Project</th>
                            <th>Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['acquisitions'] as $asset): ?>
                        <tr>
                            <td><?= formatDate($asset['acquired_date']) ?></td>
                            <td>
                                <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                    <strong><?= htmlspecialchars($asset['ref'] ?? 'N/A') ?></strong>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($asset['name']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($asset['vendor_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?></td>
                            <td><strong><?= formatCurrency($asset['acquisition_cost'] ?? 0) ?></strong></td>
                            <td>
                                <span class="badge <?= getStatusBadgeClass($asset['status']) ?>">
                                    <?= getStatusLabel($asset['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-info">
                            <th colspan="6" class="text-end">Total:</th>
                            <th><?= formatCurrency($reportData['total_spending'] ?? 0) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No acquisitions found</h5>
                <p class="text-muted">Try adjusting your date range or check if there are any assets in the system.</p>
                <a href="?route=procurement/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Procure First Asset
                </a>
            </div>
            <?php endif; ?>
        </div>
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

// Initialize DataTable if available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#acquisitionsTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']], // Sort by date descending
            columnDefs: [
                { targets: [6], type: 'currency' }, // Cost column
                { targets: [7], orderable: false }   // Status column
            ]
        });
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

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
