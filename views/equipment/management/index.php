<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-gear-fill me-2"></i>Equipment Classification Management
    </h6>
    <p class="mb-0">
        Manage equipment categories, types, and subtypes for the item management system. This affects how items are classified and tracked throughout the platform.
    </p>
</div>

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!in_array($user['role_name'], $roleConfig['equipment/management'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to manage equipment classifications.</div>
<?php else: ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Categories</h6>
                        <h3 class="mb-0"><?= $stats['total_categories'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-folder fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Equipment Types</h6>
                        <h3 class="mb-0"><?= $stats['total_equipment_types'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-list-task fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Subtypes</h6>
                        <h3 class="mb-0"><?= $stats['total_subtypes'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-tag fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Items</h6>
                        <h3 class="mb-0"><?= $stats['total_assets'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box-seam fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-lightning-fill me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <a href="/equipment/categories" class="btn btn-primary w-100">
                            <i class="bi bi-folder me-2"></i>Manage Categories
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="/equipment/types" class="btn btn-success w-100">
                            <i class="bi bi-list-task me-2"></i>Manage Equipment Types
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="/equipment/subtypes" class="btn btn-info w-100">
                            <i class="bi bi-tag me-2"></i>Manage Subtypes
                        </a>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6 mb-2">
                        <a href="/equipment/export/database" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-download me-2"></i>Export Database (SQL)
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="/equipment/export/csv?type=categories" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export to CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories Breakdown -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="bi bi-folder-fill me-2"></i>Categories Breakdown
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>ISO Code</th>
                        <th>Type</th>
                        <th class="text-center">Equipment Types</th>
                        <th class="text-center">Subtypes</th>
                        <th class="text-center">Items</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['categories_breakdown'])): ?>
                        <?php foreach ($stats['categories_breakdown'] as $category): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    <?php if (!empty($category['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($category['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($category['iso_code']) ?></span>
                                </td>
                                <td>
                                    <?php if ($category['is_consumable']): ?>
                                        <span class="badge bg-warning text-dark">Consumable</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><?= ucfirst($category['asset_type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $category['equipment_types_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $category['subtypes_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark"><?= $category['assets_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($category['generates_assets']): ?>
                                        <i class="bi bi-check-circle-fill text-success" title="Generates Items"></i>
                                    <?php else: ?>
                                        <i class="bi bi-dash-circle text-muted" title="No Items"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No categories found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout with the captured content
$pageTitle = 'Equipment Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/'],
    ['title' => 'Equipment Management', 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
