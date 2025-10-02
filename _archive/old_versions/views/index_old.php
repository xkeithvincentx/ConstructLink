<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-box-seam me-2"></i>
        Asset Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
            <a href="?route=assets/create" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-1"></i>Add Asset
            </a>
        <?php endif; ?>
        <a href="?route=assets/scanner" class="btn btn-outline-info me-2">
            <i class="bi bi-qr-code-scan me-1"></i>QR Scanner
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="refreshAssets()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'asset_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'asset_updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'asset_deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'export_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to export assets. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Maintenance Alerts -->
<?php if (!empty($assetsDueForMaintenance)): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Assets Due for Maintenance
        </h6>
        <p class="mb-2">There are <?= count($assetsDueForMaintenance) ?> asset(s) that require maintenance attention:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($assetsDueForMaintenance, 0, 3) as $asset): ?>
                <li>
                    <strong><?= htmlspecialchars($asset['name']) ?></strong> 
                    (<?= htmlspecialchars($asset['ref']) ?>) - 
                    <?= $asset['days_until_due'] > 0 ? $asset['days_until_due'] . ' days until due' : 'Overdue' ?>
                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($assetsDueForMaintenance) > 3): ?>
                <li><em>... and <?= count($assetsDueForMaintenance) - 3 ?> more</em></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Assets</h6>
                        <h3 class="mb-0"><?= $assetStats['total_assets'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box-seam display-6"></i>
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
                        <h6 class="card-title">Available</h6>
                        <h3 class="mb-0"><?= $assetStats['available'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
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
                        <h6 class="card-title">In Use</h6>
                        <h3 class="mb-0"><?= $assetStats['in_use'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-gear display-6"></i>
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
                        <h6 class="card-title">Total Value</h6>
                        <h3 class="mb-0"><?= formatCurrency($assetStats['total_value'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=assets" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="available" <?= ($_GET['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="in_use" <?= ($_GET['status'] ?? '') === 'in_use' ? 'selected' : '' ?>>In Use</option>
                    <option value="borrowed" <?= ($_GET['status'] ?? '') === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                    <option value="under_maintenance" <?= ($_GET['status'] ?? '') === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                    <option value="retired" <?= ($_GET['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Retired</option>
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
                                <?= htmlspecialchars($category['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                                <?= htmlspecialchars($project['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="maker_id" class="form-label">Manufacturer</label>
                <select class="form-select" id="maker_id" name="maker_id">
                    <option value="">All Manufacturers</option>
                    <?php if (isset($makers) && is_array($makers)): ?>
                        <?php foreach ($makers as $maker): ?>
                            <option value="<?= $maker['id'] ?>" 
                                    <?= ($_GET['maker_id'] ?? '') == $maker['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maker['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by asset name, reference, or serial number..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=assets" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Assets Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Assets</h6>
        <div class="d-flex gap-2">
            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($assets)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No assets found</h5>
                <p class="text-muted">Try adjusting your filters or add your first asset to the system.</p>
                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
                    <a href="?route=assets/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add First Asset
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="assetsTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($asset['ref'] ?? 'N/A') ?></strong>
                                    </a>
                                    <?php if (!empty($asset['qr_code'])): ?>
                                        <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-box text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($asset['name'] ?? 'Unknown') ?></div>
                                            <?php if (!empty($asset['serial_number'])): ?>
                                                <small class="text-muted">S/N: <?= htmlspecialchars($asset['serial_number']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status = $asset['status'] ?? 'unknown';
                                    $statusClasses = [
                                        'available' => 'bg-success',
                                        'in_use' => 'bg-primary',
                                        'borrowed' => 'bg-info',
                                        'under_maintenance' => 'bg-warning',
                                        'retired' => 'bg-secondary',
                                        'disposed' => 'bg-dark'
                                    ];
                                    $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($asset['acquisition_cost']): ?>
                                        <strong><?= formatCurrency($asset['acquisition_cost']) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=assets/view&id=<?= $asset['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
                                            <a href="?route=assets/edit&id=<?= $asset['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit Asset">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'available' && $auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk'])): ?>
                                            <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" 
                                               class="btn btn-outline-success" title="Withdraw Asset">
                                                <i class="bi bi-box-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($auth->hasRole(['System Admin'])): ?>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteAsset(<?= $asset['id'] ?>)" title="Delete Asset">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Assets pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=assets&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=assets&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=assets&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
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

<!-- Quick Actions -->
<?php if (!empty($assetsDueForMaintenance) || !empty($idleAssets)): ?>
<div class="row mt-4">
    <?php if (!empty($assetsDueForMaintenance)): ?>
    <div class="col-md-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Assets Due for Maintenance
                </h6>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($assetsDueForMaintenance, 0, 5) as $asset): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= htmlspecialchars($asset['name']) ?></strong>
                            <small class="text-muted d-block"><?= htmlspecialchars($asset['ref']) ?></small>
                        </div>
                        <div class="text-end">
                            <small class="text-warning">
                                <?= $asset['days_until_due'] > 0 ? $asset['days_until_due'] . ' days' : 'Overdue' ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($assetsDueForMaintenance) > 5): ?>
                    <small class="text-muted">And <?= count($assetsDueForMaintenance) - 5 ?> more...</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($idleAssets)): ?>
    <div class="col-md-6">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock me-2"></i>Idle Assets
                </h6>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($idleAssets, 0, 5) as $asset): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= htmlspecialchars($asset['name']) ?></strong>
                            <small class="text-muted d-block"><?= htmlspecialchars($asset['ref']) ?></small>
                        </div>
                        <div class="text-end">
                            <small class="text-info"><?= $asset['days_idle'] ?> days idle</small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($idleAssets) > 5): ?>
                    <small class="text-muted">And <?= count($idleAssets) - 5 ?> more...</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// Delete asset
function deleteAsset(assetId) {
    if (confirm('Are you sure you want to delete this asset? This action cannot be undone.')) {
        fetch('?route=assets/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ asset_id: assetId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete asset: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the asset');
        });
    }
}

// Refresh assets
function refreshAssets() {
    window.location.reload();
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=assets/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Enhanced search functionality
document.getElementById('search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=assets"]');
    const filterInputs = filterForm.querySelectorAll('select');
    
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
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Assets - ConstructLink™';
$pageHeader = 'Asset Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
